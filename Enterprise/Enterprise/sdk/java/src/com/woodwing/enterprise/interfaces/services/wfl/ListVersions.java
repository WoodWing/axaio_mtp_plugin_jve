/**
 * ListVersions.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class ListVersions  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String ID;

    private com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition;

    private com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas;

    public ListVersions() {
    }

    public ListVersions(
           java.lang.String ticket,
           java.lang.String ID,
           com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition,
           com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas) {
           this.ticket = ticket;
           this.ID = ID;
           this.rendition = rendition;
           this.areas = areas;
    }


    /**
     * Gets the ticket value for this ListVersions.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this ListVersions.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the ID value for this ListVersions.
     * 
     * @return ID
     */
    public java.lang.String getID() {
        return ID;
    }


    /**
     * Sets the ID value for this ListVersions.
     * 
     * @param ID
     */
    public void setID(java.lang.String ID) {
        this.ID = ID;
    }


    /**
     * Gets the rendition value for this ListVersions.
     * 
     * @return rendition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RenditionType getRendition() {
        return rendition;
    }


    /**
     * Sets the rendition value for this ListVersions.
     * 
     * @param rendition
     */
    public void setRendition(com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition) {
        this.rendition = rendition;
    }


    /**
     * Gets the areas value for this ListVersions.
     * 
     * @return areas
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AreaType[] getAreas() {
        return areas;
    }


    /**
     * Sets the areas value for this ListVersions.
     * 
     * @param areas
     */
    public void setAreas(com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas) {
        this.areas = areas;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ListVersions)) return false;
        ListVersions other = (ListVersions) obj;
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
            ((this.ID==null && other.getID()==null) || 
             (this.ID!=null &&
              this.ID.equals(other.getID()))) &&
            ((this.rendition==null && other.getRendition()==null) || 
             (this.rendition!=null &&
              this.rendition.equals(other.getRendition()))) &&
            ((this.areas==null && other.getAreas()==null) || 
             (this.areas!=null &&
              java.util.Arrays.equals(this.areas, other.getAreas())));
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
        if (getID() != null) {
            _hashCode += getID().hashCode();
        }
        if (getRendition() != null) {
            _hashCode += getRendition().hashCode();
        }
        if (getAreas() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAreas());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAreas(), i);
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
        new org.apache.axis.description.TypeDesc(ListVersions.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">ListVersions"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rendition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rendition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("areas");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Areas"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AreaType"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "AreaType"));
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
