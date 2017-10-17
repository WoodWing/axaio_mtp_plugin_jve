/**
 * Mode.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class Mode implements java.io.Serializable {
    private java.lang.String _value_;
    private static java.util.HashMap _table_ = new java.util.HashMap();

    // Constructor
    protected Mode(java.lang.String value) {
        _value_ = value;
        _table_.put(_value_,this);
    }

    public static final java.lang.String _GetPublications = "GetPublications";
    public static final java.lang.String _GetPubChannels = "GetPubChannels";
    public static final java.lang.String _GetIssues = "GetIssues";
    public static final java.lang.String _GetEditions = "GetEditions";
    public static final java.lang.String _GetSections = "GetSections";
    public static final java.lang.String _GetStatuses = "GetStatuses";
    public static final java.lang.String _GetUsers = "GetUsers";
    public static final java.lang.String _GetUserGroups = "GetUserGroups";
    public static final java.lang.String _GetProfileFeatures = "GetProfileFeatures";
    public static final java.lang.String _GetObjectInfos = "GetObjectInfos";
    public static final Mode GetPublications = new Mode(_GetPublications);
    public static final Mode GetPubChannels = new Mode(_GetPubChannels);
    public static final Mode GetIssues = new Mode(_GetIssues);
    public static final Mode GetEditions = new Mode(_GetEditions);
    public static final Mode GetSections = new Mode(_GetSections);
    public static final Mode GetStatuses = new Mode(_GetStatuses);
    public static final Mode GetUsers = new Mode(_GetUsers);
    public static final Mode GetUserGroups = new Mode(_GetUserGroups);
    public static final Mode GetProfileFeatures = new Mode(_GetProfileFeatures);
    public static final Mode GetObjectInfos = new Mode(_GetObjectInfos);
    public java.lang.String getValue() { return _value_;}
    public static Mode fromValue(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        Mode enumeration = (Mode)
            _table_.get(value);
        if (enumeration==null) throw new java.lang.IllegalArgumentException();
        return enumeration;
    }
    public static Mode fromString(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        return fromValue(value);
    }
    public boolean equals(java.lang.Object obj) {return (obj == this);}
    public int hashCode() { return toString().hashCode();}
    public java.lang.String toString() { return _value_;}
    public java.lang.Object readResolve() throws java.io.ObjectStreamException { return fromValue(_value_);}
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new org.apache.axis.encoding.ser.EnumSerializer(
            _javaType, _xmlType);
    }
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new org.apache.axis.encoding.ser.EnumDeserializer(
            _javaType, _xmlType);
    }
    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Mode.class);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Mode"));
    }
    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

}
