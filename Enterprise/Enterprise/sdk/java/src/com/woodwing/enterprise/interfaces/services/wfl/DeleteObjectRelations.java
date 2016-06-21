/**
 * DeleteObjectRelations.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class DeleteObjectRelations  implements java.io.Serializable {
    private java.lang.String ticket;

    private com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations;

    public DeleteObjectRelations() {
    }

    public DeleteObjectRelations(
           java.lang.String ticket,
           com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations) {
           this.ticket = ticket;
           this.relations = relations;
    }


    /**
     * Gets the ticket value for this DeleteObjectRelations.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this DeleteObjectRelations.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the relations value for this DeleteObjectRelations.
     * 
     * @return relations
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Relation[] getRelations() {
        return relations;
    }


    /**
     * Sets the relations value for this DeleteObjectRelations.
     * 
     * @param relations
     */
    public void setRelations(com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations) {
        this.relations = relations;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof DeleteObjectRelations)) return false;
        DeleteObjectRelations other = (DeleteObjectRelations) obj;
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
            ((this.relations==null && other.getRelations()==null) || 
             (this.relations!=null &&
              java.util.Arrays.equals(this.relations, other.getRelations())));
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
        if (getRelations() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRelations());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRelations(), i);
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
        new org.apache.axis.description.TypeDesc(DeleteObjectRelations.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectRelations"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("relations");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Relations"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Relation"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Relation"));
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
