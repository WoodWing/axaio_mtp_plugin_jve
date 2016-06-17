/**
 * UpdateDossierOrderRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class UpdateDossierOrderRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.pub.PublishTarget target;

    private java.lang.String[] newOrder;

    private java.lang.String[] originalOrder;

    public UpdateDossierOrderRequest() {
    }

    public UpdateDossierOrderRequest(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.pub.PublishTarget target,
           java.lang.String[] newOrder,
           java.lang.String[] originalOrder) {
           this.ticket = ticket;
           this.target = target;
           this.newOrder = newOrder;
           this.originalOrder = originalOrder;
    }


    /**
     * Gets the ticket value for this UpdateDossierOrderRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this UpdateDossierOrderRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the target value for this UpdateDossierOrderRequest.
     * 
     * @return target
     */
    public com.woodwing.enterprise.interfaces.services.pub.PublishTarget getTarget() {
        return target;
    }


    /**
     * Sets the target value for this UpdateDossierOrderRequest.
     * 
     * @param target
     */
    public void setTarget(com.woodwing.enterprise.interfaces.services.pub.PublishTarget target) {
        this.target = target;
    }


    /**
     * Gets the newOrder value for this UpdateDossierOrderRequest.
     * 
     * @return newOrder
     */
    public java.lang.String[] getNewOrder() {
        return newOrder;
    }


    /**
     * Sets the newOrder value for this UpdateDossierOrderRequest.
     * 
     * @param newOrder
     */
    public void setNewOrder(java.lang.String[] newOrder) {
        this.newOrder = newOrder;
    }


    /**
     * Gets the originalOrder value for this UpdateDossierOrderRequest.
     * 
     * @return originalOrder
     */
    public java.lang.String[] getOriginalOrder() {
        return originalOrder;
    }


    /**
     * Sets the originalOrder value for this UpdateDossierOrderRequest.
     * 
     * @param originalOrder
     */
    public void setOriginalOrder(java.lang.String[] originalOrder) {
        this.originalOrder = originalOrder;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof UpdateDossierOrderRequest)) return false;
        UpdateDossierOrderRequest other = (UpdateDossierOrderRequest) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.target==null && other.getTarget()==null) || 
             (this.target!=null &&
              this.target.equals(other.getTarget()))) &&
            ((this.newOrder==null && other.getNewOrder()==null) || 
             (this.newOrder!=null &&
              java.util.Arrays.equals(this.newOrder, other.getNewOrder()))) &&
            ((this.originalOrder==null && other.getOriginalOrder()==null) || 
             (this.originalOrder!=null &&
              java.util.Arrays.equals(this.originalOrder, other.getOriginalOrder())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getTarget() != null) {
            _hashCode += getTarget().hashCode();
        }
        if (getNewOrder() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getNewOrder());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getNewOrder(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getOriginalOrder() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getOriginalOrder());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getOriginalOrder(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(UpdateDossierOrderRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossierOrderRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("target");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Target"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishTarget"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("newOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "NewOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("originalOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "OriginalOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "String"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
