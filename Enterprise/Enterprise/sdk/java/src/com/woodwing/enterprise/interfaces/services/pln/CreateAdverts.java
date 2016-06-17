/**
 * CreateAdverts.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public class CreateAdverts  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String layoutId;

    private java.lang.String layoutName;

    private com.woodwing.enterprise.interfaces.services.pln.Advert[] adverts;

    public CreateAdverts() {
    }

    public CreateAdverts(
           java.lang.String ticket,
           java.lang.String layoutId,
           java.lang.String layoutName,
           com.woodwing.enterprise.interfaces.services.pln.Advert[] adverts) {
           this.ticket = ticket;
           this.layoutId = layoutId;
           this.layoutName = layoutName;
           this.adverts = adverts;
    }


    /**
     * Gets the ticket value for this CreateAdverts.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this CreateAdverts.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the layoutId value for this CreateAdverts.
     * 
     * @return layoutId
     */
    public java.lang.String getLayoutId() {
        return layoutId;
    }


    /**
     * Sets the layoutId value for this CreateAdverts.
     * 
     * @param layoutId
     */
    public void setLayoutId(java.lang.String layoutId) {
        this.layoutId = layoutId;
    }


    /**
     * Gets the layoutName value for this CreateAdverts.
     * 
     * @return layoutName
     */
    public java.lang.String getLayoutName() {
        return layoutName;
    }


    /**
     * Sets the layoutName value for this CreateAdverts.
     * 
     * @param layoutName
     */
    public void setLayoutName(java.lang.String layoutName) {
        this.layoutName = layoutName;
    }


    /**
     * Gets the adverts value for this CreateAdverts.
     * 
     * @return adverts
     */
    public com.woodwing.enterprise.interfaces.services.pln.Advert[] getAdverts() {
        return adverts;
    }


    /**
     * Sets the adverts value for this CreateAdverts.
     * 
     * @param adverts
     */
    public void setAdverts(com.woodwing.enterprise.interfaces.services.pln.Advert[] adverts) {
        this.adverts = adverts;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CreateAdverts)) return false;
        CreateAdverts other = (CreateAdverts) obj;
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
            ((this.layoutId==null && other.getLayoutId()==null) || 
             (this.layoutId!=null &&
              this.layoutId.equals(other.getLayoutId()))) &&
            ((this.layoutName==null && other.getLayoutName()==null) || 
             (this.layoutName!=null &&
              this.layoutName.equals(other.getLayoutName()))) &&
            ((this.adverts==null && other.getAdverts()==null) || 
             (this.adverts!=null &&
              java.util.Arrays.equals(this.adverts, other.getAdverts())));
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
        if (getLayoutId() != null) {
            _hashCode += getLayoutId().hashCode();
        }
        if (getLayoutName() != null) {
            _hashCode += getLayoutName().hashCode();
        }
        if (getAdverts() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAdverts());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAdverts(), i);
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
        new org.apache.axis.description.TypeDesc(CreateAdverts.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateAdverts"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layoutId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LayoutId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layoutName");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LayoutName"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("adverts");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Adverts"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Advert"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Advert"));
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
